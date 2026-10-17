import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/document.dart';
import '../data/document_repository.dart';

part 'document_event.dart';
part 'document_state.dart';

class DocumentBloc extends Bloc<DocumentEvent, DocumentState> {
  final DocumentRepository repository;

  DocumentBloc({required this.repository}) : super(DocumentInitial()) {
    on<FetchDriverDocuments>(_onFetchDriverDocuments);
    on<FetchVehicleDocuments>(_onFetchVehicleDocuments);
  }

  Future<void> _onFetchDriverDocuments(
    FetchDriverDocuments event,
    Emitter<DocumentState> emit,
  ) async {
    emit(DocumentLoading());
    try {
      final documents = await repository.getDriverDocuments(
        type: event.type,
        status: event.status,
      );
      emit(DocumentsLoaded(documents));
    } catch (e) {
      emit(DocumentError(e.toString()));
    }
  }

  Future<void> _onFetchVehicleDocuments(
    FetchVehicleDocuments event,
    Emitter<DocumentState> emit,
  ) async {
    emit(DocumentLoading());
    try {
      final documents = await repository.getVehicleDocuments(
        vehicleUuid: event.vehicleUuid,
        type: event.type,
        status: event.status,
      );
      emit(DocumentsLoaded(documents));
    } catch (e) {
      emit(DocumentError(e.toString()));
    }
  }
}
